//
//  HymnCollectionViewCell.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/30/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit
import CoreData

class HymnCollectionViewCell: UICollectionViewCell, UITableViewDataSource, UITableViewDelegate {
    
    @IBOutlet weak var stanzasTableView: UITableView!
    @IBOutlet weak var hymnNumberLabel: UILabel!
    @IBOutlet weak var hymnTitleLabel: UILabel!
    
    var hymn: NSManagedObject!
    var stanzas = [AnyObject]()

    
    
    func initWith(theHymn:NSManagedObject) {
        hymn = theHymn
        stanzasTableView!.delegate = self
        stanzasTableView.rowHeight = UITableViewAutomaticDimension
        stanzasTableView.estimatedRowHeight = 200
        
        let stanzasSet = hymn.value(forKeyPath: "stanzas") as! NSMutableOrderedSet
        stanzas.removeAll()
        stanzas = Array(stanzasSet) as [AnyObject]
        let title: NSString = (hymn.value(forKey: "title") as? NSString)!
        hymnTitleLabel.text = title as? String
        hymnNumberLabel.text = hymn.value(forKey: "number") as? String
        
        //Calculate the expected size based on the font and linebreak mode of your label
        // FLT_MAX here simply means no constraint in height
        //let maximumLabelSize = CGSize(width: 296, height: CGFloat(FLT_MAX));
        //UIFont.systemFontOfSize(14.0)
        //UIFont(name:"Avenir", size:23)
        let size: CGSize = title.size(attributes: [NSFontAttributeName: UIFont.systemFont(ofSize: 34.0)])
        
        //adjust the label the the new height.
        var newFrame = hymnTitleLabel.frame
        newFrame.size.height = size.height
        hymnTitleLabel.frame = newFrame
        
        print("size", size, "newFrame", newFrame)
//        CGRect newFrame = hymnTitleLabel.frame;
//        newFrame.size.height = expectedLabelSize.height;
//        yourLabel.frame = newFrame;
       
        stanzasTableView.reloadData()
    }
    

    func numberOfSections(in tableView: UITableView) -> Int {
        // #warning Incomplete implementation, return the number of sections
        return 1
    }
    
    func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        // #warning Incomplete implementation, return the number of rows
        return stanzas.count
    }
    
    //    func tableView(_ tableView: UITableView, heightForRowAt indexPath: IndexPath) -> CGFloat {
    //        let cell = tableView(tableView, cellForRowAt: indexPath)
    //        let height = cell.stanzaTextView.frame.size.height
    //        return height
    //    }
    
    
    func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        let cell = tableView.dequeueReusableCell(withIdentifier: "StanzaCell", for: indexPath) as! StanzaTableViewCell
        let stanza = stanzas[indexPath.row]
        let number = stanza.value(forKey: "number") as! String
        cell.numberLabel.text = number
        let codedString = stanza.value(forKeyPath: "text") as! String
        //cell.stanzaTextView.attributedText = decodeString(string:codedString)
        cell.stanzaTextView.text = decodeString(string:codedString)
        print(stanza.value(forKeyPath: "text") as! String)
        
        
        cell.stanzaTextView.isScrollEnabled = false

        cell.stanzaTextView.layoutIfNeeded()
        
        return cell
    }
    
    func decodeString(string: String) -> String {
        var newString = string.replacingOccurrences(of: "&mdash;", with: "—")
        newString = newString.replacingOccurrences(of: "  ", with: "")
        newString = newString.replacingOccurrences(of: "\n&#9;", with: "")
        
        return newString
     //Attributed strings were removing the line breaks
//        let stringData: Data = string.data(using: String.Encoding.utf8)!
//        let options: NSDictionary = [NSDocumentTypeDocumentAttribute:NSHTMLTextDocumentType]
//        var decodedString: NSMutableAttributedString = NSMutableAttributedString()
//        do {
//            decodedString = try NSMutableAttributedString(data:stringData, options:options as! [String : Any], documentAttributes:nil)
//            
//        }
//        catch {
//            // string didn't convert
//        }
//        decodedString.addAttribute(NSFontAttributeName, value:UIFont(name:"Times New Roman", size:20.0)!, range:NSRange(location:0, length:decodedString.length-1))
//        
//        return decodedString.mutableString as String
//        //return decodedString as NSAttributedString
        
    }
    
    
    
    /*
     // Override to support conditional editing of the table view.
     override func tableView(_ tableView: UITableView, canEditRowAt indexPath: IndexPath) -> Bool {
     // Return false if you do not want the specified item to be editable.
     return true
     }
     */
    
    /*
     // Override to support editing the table view.
     override func tableView(_ tableView: UITableView, commit editingStyle: UITableViewCellEditingStyle, forRowAt indexPath: IndexPath) {
     if editingStyle == .delete {
     // Delete the row from the data source
     tableView.deleteRows(at: [indexPath], with: .fade)
     } else if editingStyle == .insert {
     // Create a new instance of the appropriate class, insert it into the array, and add a new row to the table view
     }
     }
     */
    
    /*
     // Override to support rearranging the table view.
     override func tableView(_ tableView: UITableView, moveRowAt fromIndexPath: IndexPath, to: IndexPath) {
     
     }
     */
    
    /*
     // Override to support conditional rearranging of the table view.
     override func tableView(_ tableView: UITableView, canMoveRowAt indexPath: IndexPath) -> Bool {
     // Return false if you do not want the item to be re-orderable.
     return true
     }
     */

    
}
