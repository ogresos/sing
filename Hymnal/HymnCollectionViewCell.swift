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
    
    @IBOutlet weak var versesTableView: UITableView!
    @IBOutlet weak var hymnNumberLabel: UILabel!
    @IBOutlet weak var hymnTitleLabel: UILabel!
    
    var hymn: NSManagedObject!
    var verses = [AnyObject]()

    
    
    func initWith(theHymn:NSManagedObject) {
        hymn = theHymn
        versesTableView!.delegate = self
        versesTableView.rowHeight = UITableViewAutomaticDimension
        versesTableView.estimatedRowHeight = 200
        
        let versesSet = hymn.value(forKeyPath: "verses") as! NSMutableOrderedSet
        verses.removeAll()
        verses = Array(versesSet)
        hymnTitleLabel.text = hymn.value(forKey: "title") as? String
        hymnNumberLabel.text = String(hymn.value(forKey: "number") as! Int)
        versesTableView.reloadData()
    }
    
    func numberOfSections(in tableView: UITableView) -> Int {
        // #warning Incomplete implementation, return the number of sections
        return 1
    }
    
    func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        // #warning Incomplete implementation, return the number of rows
        return verses.count
    }
    
    //    func tableView(_ tableView: UITableView, heightForRowAt indexPath: IndexPath) -> CGFloat {
    //        let cell = tableView(tableView, cellForRowAt: indexPath)
    //        let height = cell.verseTextView.frame.size.height
    //        return height
    //    }
    
    
    func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        let cell = tableView.dequeueReusableCell(withIdentifier: "VerseCell", for: indexPath) as! VerseTableViewCell
        let verse = verses[indexPath.row]
        let verseNumber = verse.value(forKey: "verseNumber") as! String
        cell.verseNumberLabel.text = verseNumber
        cell.verseTextView.text = verse.value(forKeyPath: "text") as! String
        
        
        cell.verseTextView.isScrollEnabled = false
        //        let contentSize = cell.verseTextView.sizeThatFits(cell.verseTextView.bounds.size)
        //        var frame = cell.verseTextView.frame
        //        frame.size.height = contentSize.height
        //        cell.verseTextView.frame = frame
        
        //let aspectRatioTextViewConstraint = NSLayoutConstraint(item: cell.verseTextView, attribute: .height, relatedBy: .equal, toItem: cell.verseTextView,attribute: .width, multiplier: cell.verseTextView.bounds.height/cell.verseTextView.bounds.width, constant: 1)
        //cell.verseTextView.addConstraint(aspectRatioTextViewConstraint)
        cell.verseTextView.layoutIfNeeded()
        
        return cell
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
