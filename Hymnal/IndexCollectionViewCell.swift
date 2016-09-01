//
//  IndexCollectionViewCell.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/30/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit
import CoreData

class IndexCollectionViewCell: UICollectionViewCell {
    
    @IBOutlet weak var hymnNumberLabel: UILabel!
    @IBOutlet weak var hymnTitleLabel: UILabel!
    
    var hymn: NSManagedObject!
    
    func initWith(theHymn:NSManagedObject) {
        hymn = theHymn
        hymnTitleLabel.text = hymn.value(forKey: "title") as? String
        hymnNumberLabel.text = String(hymn.value(forKey: "number") as! Int)
    }
    
    
}
